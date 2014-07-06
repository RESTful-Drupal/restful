
<div ng-app="restfulApp" ng-controller="MainCtrl">
  <form name="article" ng-submit="submitForm()">

    <div class="text">
      <label>Title</label>
      <input id="label" name="label" type="text" ng-model="data.label" placeholder="See how many characters are needed to validate..." ng-required="required" size="60">
    </div>

    <div class="textarea">
      <label>Description</label>
      <textarea id="body" name="body" type="textarea" ng-model="data.body" placeholder="Type some description. See which word is required..." rows="3" ng-minlength="3" ng-required="required" cols="60"></textarea>
    </div>

    <input ng-show="!dropSupported" type="file" ng-file-select="onFileSelect($files)" >
    <div ng-file-drop="onFileSelect($files)" ng-show="dropSupported">drop files here</div>

    <div class="actions">
      <button type="submit" class="btn btn-default btn-lg btn-block" ng-class="{'btn-primary':<?php print $data; ?>.$valid}" tabindex="100">Submit</button>
    </div>
  </form>

  <h2>Console (Server side)</h2>

  <div ng-show="serverSide">
    <div>
      Status: {{ serverSide.status }}
    </div>
    <div>
      Data: {{ serverSide.data }}
    </div>

    <div>
      File ID: {{ serverSide.image.id }} <a ng-href="{{ serverSide.image.self }}" target="_blank">{{ serverSide.image.label }}</a>
    </div>

    <div ng-show="serverSide.status == 200">
      New article: <a ng-href="{{ serverSide.data.self }}" target="_blank">{{ serverSide.data.label }}</a>
    </div>
  </div>
</div>
